function event_say(e)
	local is_special_flag_response = false;
	if(e.message:findi("Hail")) then
		e.self:Say("Greetings, " .. e.other:GetCleanName() .. " .  Are you a child of Order?  If you have come seeking the path of Discord. I require only that you give me your [Tome of Order and Discord] and I shall show you the way.  Only then will you be freed from Order's confining restraints. If you are drawn to the allure of the uncharted, inquire about the secret [challenges] and their hidden [rites]. These are not mere adventures, but tests of your true mettle.");
	elseif(e.message:findi("tome")) then
		e.self:Say("The Tome of Order and Discord was penned by the seventh member of the Tribunal and has become the key to a life of Discord, in spite of the author's pitiful warnings.  Do you not have one, child of Order?  Would you [like to read] it?");
	elseif(e.message:findi("read")) then
		e.self:Say("Very well. Here you go. Simply return it to me to be released from the chains of Order.");
		e.other:SetPVP(false);
		e.other:SummonCursorItem(18700); -- Item: Tome of Order and Discord
	elseif(e.message:findi("rites")) then 
		if(e.other:IsSelfFound() == 1 or e.other:IsSoloOnly() == 1) then
			e.self:Say("The path to greatness is paved with the rarest of elements. Find those who deal in silver and gold. Secure a piece of such wealth and seek guidance on how to imbue it with arcane energy.");
		else
			e.self:Say("Ah, you tread the common path, where certain secrets remain veiled.");
		end
	end
	if(e.other:GetLevel() == 1 and e.other:IsSelfFound() == 0 and e.other:IsSoloOnly() == 0 and e.other:IsHardcore() == 0) then
		if(e.message:findi("challenges")) then
			-- TODO: Would like to add more flavor here, potentially splitting this into multiple descriptions, one for each challenge mode
			e.self:Say("I can offer you flags for the [solo], [self found], and [hardcore] challenges. You must tell me all of the challenges you wish to embark on in the same sentence. In the [solo] challenge, all external interactions become unavailable - as well as external buffs. In the [self found] challenge you are prevented from interacting with anyone else except others with the same flags of similar level, though you are additionally prevented from trading. There is also the [hardcore] challenge, which will result in your mortal coil being emptied - permanently - on death. You may include all three of these challenges together.");
			e.other:Message(13, "By accepting any of these options, you will immediately be completely reset and sent back to your starting location. This will be irreversible.");
		end
	
		if(e.message:findi("solo")) then
			e.self:Say("Very well. You will now play solo, without any friends.");
			e.other:SetSoloOnly(1);
			is_special_flag_response = true;
		end
		if(e.message:findi("self found")) then
			e.self:Say("Very well. You will now play through the game using the self found ruleset.");
			e.other:SetSelfFound(1);
			is_special_flag_response = true;
		end
		if(e.message:findi("hardcore")) then
			e.self:Say("Very well. You will now have your character permanently unavailable upon your next death, along with all of their items.");
			e.other:SetHardcore(1);
			is_special_flag_response = true;
		end
		
		if(e.message:findi("i want to suffer")) then
			e.self:Say("Very well, then. Welcome to true Discord.");
			e.other:SetSelfFound(1);
			e.other:SetHardcore(1);
			e.other:SetBaseClass(0);
			is_special_flag_response = true;
		end
		
		if(is_special_flag_response) then
			e.other:ClearPlayerInfoAndGrantStartingItems();
		end
	else
		if(e.message:findi("challenges")) then
		e.self:Say("I can't offer you anything as you are above the first season, or have already chosen your challenges. Begone, mortal.");
		elseif(e.message:findi("solo")) then
			e.self:Say("I can't offer you anything as you are above the first season, or have already chosen your challenges. Begone, mortal.");
		elseif(e.message:findi("self found")) then
			e.self:Say("I can't offer you anything as you are above the first season, or have already chosen your challenges. Begone, mortal.");
		elseif(e.message:findi("hardcore")) then
			e.self:Say("I can't offer you anything as you are above the first season, or have already chosen your challenges. Begone, mortal.");
		end
	end
end

function event_trade(e)
	local item_lib = require("items");
	if(item_lib.check_turn_in(e.self, e.trade, {item1 = 18700})) then
		e.self:Say("I see you wish to join us in Discord! Welcome! By turning your back on the protection of Order you are now open to many more opportunities for glory and power. Remember that you can now be harmed by those who have also heard the call of Discord.");
		e.other:SetPVP(true);
		e.other:Ding();
	end
end

-------------------------------------------------------------------------------------------------
-- Converted to .lua using MATLAB converter written by Stryd and manual edits by Speedz
-- Solo, SF and Hardcore options added by Secrets
-- Find/replace data for .pl --> .lua conversions provided by Speedz, Stryd, Sorvani and Robregen
-------------------------------------------------------------------------------------------------
