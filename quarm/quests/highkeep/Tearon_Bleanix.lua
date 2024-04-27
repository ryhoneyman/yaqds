function event_say(e)
	local fac = e.other:GetFaction(e.self);
	local expansion_flag = eq.get_current_expansion();
	if(e.message:findi("hail")) then
		e.self:Say("Hello sir. Are you a citizen of Highpass?");
	elseif(e.message:findi("I am not a citizen")) then
		e.self:Say("Nor am I. I find this city's love of greed appalling. Do not you?");
	elseif(e.message:findi("appalling")) then
		e.self:Say("Yes. This city of vices is second only to Neriak. And it is trouble with Neriak that has sent me here, in search of my people's princess.");
	elseif(e.message:findi("princess")) then
		e.self:Say("The Princess Lenya Thex is the daughter of His Royal Majesty, King Tearis Thex of Felwithe. She was on her way to Qeynos when we believe she was kidnapped by Carson McCabe, the governor of this vile city. I await the paladin from Felwithe.");
	elseif(e.message:findi("all is not bright above the clouds")) then
		if(e.other:GetFactionValue(e.self) >= 500) then
			e.self:Say("Taken from this place she has been.  Seek the Highpass hussy.  Ask of her.  Only she knows where.  Find the Princess.  Give her this.  Show your allegiance.  This and her key. Then return the room key to me with the prize from the princess.  Become a hero!!");
			e.other:SummonCursorItem(13108); -- Item: Tearons Bracer
		elseif(e.other:GetFactionValue(e.self) >= 0) then
			e.self:Say("When you have furthered your service to the Paladins of Tunare, we shall make conversation.");
		else
			e.self:Say("You have some nerve to approach a loyal member of the Paladins of Tunare! Run, while you can!");		
		end
	elseif(e.message:findi("become a hero")) then
		if(fac < 4) then
			e.self:Say("Well let's get started on making you a hero. You must take the Elite Guard Bracer. Look for Princess Lenya. When you find her give her the bracer to prove you are with the Koada'dal. She should trust you then. Then return to me with Princess Lenya and return my bracer. Be safe my friend.");
			e.other:SummonCursorItem(13108); -- Item: Tearons Bracer
		else
			e.self:Say("When you have furthered your service to the Paladins of Tunare, we shall make conversation.");
		end
	end
end

function event_trade(e)
	local item_lib = require("items");

	if(item_lib.check_turn_in(e.self, e.trade, {item1 = 12267,item2 = 13109})) then
		e.self:Say("Peace..  I can rest now.  You now hold my Silent Watch Shield.  Protect Felwithe..");
		e.other:Faction(e.self,226,25); -- Faction: Clerics of Tunare
		e.other:Faction(e.self,279,25); -- Faction: King Tearis Thex
		e.other:Faction(e.self,5002,18); -- Faction: Anti-mage
		e.other:QuestReward(e.self,0,0,0,0,9312,0); -- Item: Silent Watch Shield
		eq.depop_with_timer();
	end
		if(item_lib.check_turn_in(e.self, e.trade, {item1 = 13112})) then
		e.self:Say("King Tearis Thex thanks you my friend. Could you please hand the princess this amulet. It is hers. I pryed it from the hands of some beggar.");
		e.other:Faction(e.self,226,25); -- Faction: Clerics of Tunare
		e.other:Faction(e.self,279,25); -- Faction: King Tearis Thex
		e.other:Faction(e.self, 5002, 25); -- Faction: Anti-mage
		e.other:QuestReward(e.self,0,0,0,0,13109,0); -- Item: Thex Amulet
		eq.unique_spawn(6573,0,0,52.0,13.0,3.70,161); -- Princess
	end
	item_lib.return_items(e.self, e.other, e.trade)
end
